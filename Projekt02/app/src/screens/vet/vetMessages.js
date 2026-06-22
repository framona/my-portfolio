import React, { useState, useEffect, useRef } from 'react';
import { 
    View, Text, StyleSheet, FlatList, TextInput, 
    TouchableOpacity, KeyboardAvoidingView, Platform,
    SafeAreaView, ActivityIndicator, Alert, StatusBar, Modal
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function VetMessagesScreen() {
    const [vetId, setVetId] = useState(null);
    const [loading, setLoading] = useState(true);
    const [clients, setClients] = useState([]);
    const [selectedClient, setSelectedClient] = useState(null);
    const [messages, setMessages] = useState([]);
    const [inputText, setInputText] = useState('');
    const [showClientList, setShowClientList] = useState(false);
    
    const flatListRef = useRef();

    useEffect(() => {
        const initialize = async () => {
            try {
                const id = await AsyncStorage.getItem('user_id');
                const token = await AsyncStorage.getItem('user_token');
                if (id) setVetId(parseInt(id));
                await fetchClients(token);
            } catch (e) {
                setLoading(false);
            }
        };
        initialize();
    }, []);

    useEffect(() => {
        let interval;
        if (selectedClient && vetId) {
            fetchMessages();
            interval = setInterval(fetchMessages, 4000);
        }
        return () => clearInterval(interval);
    }, [selectedClient, vetId]);

    const fetchClients = async (token) => {
        try {
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_vet.php?action=get_clients&api_token=${token}`);
            const data = await res.json();
            if (data.success && data.clients) {
                setClients(data.clients);
                if (data.clients.length > 0 && !selectedClient) {
                    setSelectedClient(data.clients[0].id);
                }
            }
        } catch (err) { 
            console.log(err);
        } finally { 
            setLoading(false); 
        }
    };

    const fetchMessages = async () => {
        if (!selectedClient || !vetId) return;
        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_messages.php?action=get_messages&other_id=${selectedClient}&api_token=${token}`);
            const data = await res.json();
            if (data.success) {
                setMessages(data.messages || []);
            }
        } catch (e) { 
            console.log(e); 
        }
    };

    const handleSend = async () => {
        const trimmedMsg = inputText.trim();
        if (!trimmedMsg || !selectedClient) return;

        try {
            const token = await AsyncStorage.getItem('user_token');
            const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_messages.php?action=send&api_token=${token}`, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ 
                    receiver_id: parseInt(selectedClient), 
                    message: trimmedMsg 
                })
            });
            const data = await res.json();
            if (data.success) { 
                setInputText(''); 
                fetchMessages(); 
                setTimeout(() => flatListRef.current?.scrollToEnd({ animated: true }), 100);
            } else {
                Alert.alert("Error", data.error || "Send failed");
            }
        } catch (e) { 
            Alert.alert("Error", "Network error!"); 
        }
    };

    const getCurrentClientName = () => {
        const client = clients.find(c => c.id == selectedClient);
        return client ? `${client.first_name} ${client.last_name}` : "Select Client";
    };

    if (loading) return (
        <View style={styles.center}><ActivityIndicator size="large" color="#6BBB77" /></View>
    );

    return (
        <View style={styles.container}>
            <StatusBar barStyle="light-content" />
            <SafeAreaView style={{backgroundColor: '#6BBB77'}} />
            
            <View style={styles.navbar}>
                <TouchableOpacity onPress={() => setShowClientList(true)} style={styles.menuBtn}>
                    <Text style={{fontSize: 24}}>👤</Text> 
                </TouchableOpacity>
                <Text style={styles.navTitle} numberOfLines={1}>{getCurrentClientName()}</Text>
                <View style={{width: 40}} /> 
            </View>

            <KeyboardAvoidingView 
                behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                style={{flex: 1}}
                keyboardVerticalOffset={Platform.OS === 'ios' ? 90 : 0}
            >
                <FlatList
                    ref={flatListRef}
                    data={messages}
                    keyExtractor={(item) => item.id.toString()}
                    onContentSizeChange={() => flatListRef.current?.scrollToEnd({ animated: true })}
                    onLayout={() => flatListRef.current?.scrollToEnd({ animated: true })}
                    contentContainerStyle={{ padding: 15, paddingBottom: 30 }}
                    renderItem={({ item }) => (
                        <View style={[
                            styles.message, 
                            item.sender_id == vetId ? styles.msgUser : styles.msgOther
                        ]}>
                            <Text style={[styles.msgText, item.sender_id == vetId && { color: '#fff' }]}>
                                {item.message}
                            </Text>
                            <Text style={[styles.msgTime, item.sender_id == vetId && { color: '#E8F5E9' }]}>
                                {item.created_at ? item.created_at.split(' ')[1].substring(0,5) : ""}
                            </Text>
                        </View>
                    )}
                />

                <View style={styles.sendBoxContainer}>
                    <View style={styles.sendBox}>
                        <TextInput 
                            style={styles.input}
                            value={inputText}
                            onChangeText={setInputText}
                            placeholder="Message to owner..."
                            placeholderTextColor="#999"
                            multiline={false}
                        />
                        <TouchableOpacity style={styles.sendBtn} onPress={handleSend}>
                            <Text style={styles.sendBtnText}>Send</Text>
                        </TouchableOpacity>
                    </View>
                </View>
            </KeyboardAvoidingView>

            <Modal transparent={true} visible={showClientList} animationType="fade">
                <TouchableOpacity 
                    style={styles.modalOverlay} 
                    activeOpacity={1} 
                    onPress={() => setShowClientList(false)}
                >
                    <View style={styles.sideDrawer}>
                        <SafeAreaView style={{flex: 1}}>
                            <Text style={styles.drawerTitle}>Clients</Text>
                            <FlatList 
                                data={clients}
                                keyExtractor={(item) => item.id.toString()}
                                renderItem={({ item }) => (
                                    <TouchableOpacity 
                                        style={[styles.vetItem, selectedClient == item.id && styles.vetActive]}
                                        onPress={() => {
                                            setSelectedClient(item.id);
                                            setShowClientList(false);
                                            setMessages([]); 
                                        }}
                                    >
                                        <Text style={[styles.vetName, selectedClient == item.id && styles.vetNameActive]}>
                                            {item.first_name} {item.last_name}
                                        </Text>
                                        <Text style={styles.petNamesText}>{item.pet_names || "No pets"}</Text>
                                    </TouchableOpacity>
                                )}
                            />
                        </SafeAreaView>
                    </View>
                </TouchableOpacity>
            </Modal>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#f1f3f5' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    navbar: {
        height: 60,
        backgroundColor: '#6BBB77',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: 15
    },
    menuBtn: { padding: 5 },
    navTitle: { color: '#fff', fontSize: 18, fontWeight: 'bold', flex: 1, textAlign: 'center' },
    message: { marginBottom: 10, maxWidth: '85%', padding: 12, borderRadius: 15 },
    msgUser: { backgroundColor: '#6BBB77', alignSelf: 'flex-end', borderBottomRightRadius: 2 },
    msgOther: { backgroundColor: '#fff', alignSelf: 'flex-start', borderBottomLeftRadius: 2, borderWidth: 1, borderColor: '#ddd' },
    msgText: { fontSize: 15, color: '#333' },
    msgTime: { fontSize: 10, color: '#6c757d', marginTop: 4, alignSelf: 'flex-end' },
    sendBoxContainer: { backgroundColor: '#fff' },
    sendBox: { 
        flexDirection: 'row', 
        padding: 10, 
        alignItems: 'center',
        borderTopWidth: 1,
        borderTopColor: '#eee',
        marginBottom: Platform.OS === 'ios' ? 25 : 0
    },
    input: { flex: 1, backgroundColor: '#f1f3f5', borderRadius: 20, paddingHorizontal: 15, height: 40, marginRight: 10, color: '#333' },
    sendBtn: { backgroundColor: '#6BBB77', paddingVertical: 10, paddingHorizontal: 18, borderRadius: 20 },
    sendBtnText: { color: '#fff', fontWeight: 'bold' },
    modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)' },
    sideDrawer: { width: '80%', height: '100%', backgroundColor: '#fff', padding: 20, borderTopRightRadius: 20, borderBottomRightRadius: 20 },
    drawerTitle: { fontSize: 22, fontWeight: 'bold', marginBottom: 20, color: '#333', marginTop: 10 },
    vetItem: { paddingVertical: 15, borderBottomWidth: 1, borderBottomColor: '#f0f0f0', paddingHorizontal: 10 },
    vetActive: { backgroundColor: '#E8F5E9', borderRadius: 10 },
    vetName: { fontSize: 16, color: '#444' },
    vetNameActive: { color: '#6BBB77', fontWeight: 'bold' },
    petNamesText: { fontSize: 12, color: '#999', marginTop: 2 }
});